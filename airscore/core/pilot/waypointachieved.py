"""
Waypoint Achieved Library

contains WaypointAchieved Dataclass.

Element of FlightResult waipoints_achieved attribute list

- AirScore -
Stuart Mackintosh - Antonio Golfari
2020

"""

from dataclasses import dataclass, asdict
from sqlalchemy.exc import SQLAlchemyError
from db.conn import db_session


@dataclass
class WaypointAchieved:
    trw_id: int or None  # probably not needed as deleted each full-rescore. maybe to validate wpt by admin?
    wpt_id: int or None
    name: str
    rawtime: int
    lat: float
    lon: float
    altitude: int

    @staticmethod
    def from_dict(d: dict):
        return WaypointAchieved(**d)


def get_waypoints_achieved(track_id):
    """retrieves a WaypointAchieved obj list for track_id result"""
    from db.tables import TblTrackWaypoint
    from sqlalchemy.orm import aliased
    t = aliased(TblTrackWaypoint)
    achieved = []
    with db_session() as db:
        rows = db.query(t.trw_id, t.wpt_id, t.name, t.rawtime,
                        t.lat, t.lon, t.altitude).filter_by(track_id=track_id).all()
        for w in rows:
            achieved.append(WaypointAchieved(**w._asdict()))
    return achieved


def update_waypoints_achieved(pilot):
    """deletes old entries and updates TblTrackWaypoint for result"""
    from db.tables import TblTrackWaypoint
    mappings = []
    for w in pilot.waypoints_achieved:
        mappings.append(dict(track_id=pilot.track_id, **asdict(w)))
    with db_session() as db:
        '''delete old entries'''
        db.query(TblTrackWaypoint).filter_by(track_id=pilot.track_id).delete()
        '''insert new rows'''
        db.bulk_insert_mappings(TblTrackWaypoint, mappings)
        db.commit()
